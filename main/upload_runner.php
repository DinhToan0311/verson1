<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Uploading...</title>
</head>

<body style="display:none">
    <script>
        // Tạo kênh broadcast
        const channel = new BroadcastChannel("upload-progress");

        window.addEventListener("message", async (e) => {
            const {
                video,
                thumb
            } = e.data;
            const info = JSON.parse(sessionStorage.getItem("upload_info") || "{}");

            if (!video || !info.title || !info.category) {
                alert("❌ Thiếu dữ liệu để upload.");
                return;
            }

            const formData = new FormData();
            formData.append("file", video);
            formData.append("upload_preset", "ml_default");
            formData.append("folder", "videos/folder"); // thư mục trên Cloudinary
            formData.append("public_id", `${Date.now()}`); // tên video duy nhất


            const xhr = new XMLHttpRequest();
            xhr.open("POST", "https://api.cloudinary.com/v1_1/dz5rz7doo/video/upload");

            // 📤 Gửi tiến trình cho tab chính + broadcast cho mọi tab
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);

                    // Gửi cho tab mở form upload
                    window.opener.postMessage({
                        type: "progress",
                        percent
                    }, "*");

                    // Gửi cho mọi tab khác qua broadcast
                    channel.postMessage({
                        type: "upload-progress",
                        progress: percent
                    });
                }
            };

            xhr.onload = async () => {
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (!res.secure_url || !res.public_id) {
                        alert("❌ Upload video thất bại.");
                        window.opener.postMessage({
                            type: "error"
                        }, "*");
                        channel.postMessage({
                            type: "upload-finished"
                        }); // ❌ hoặc gửi lỗi riêng nếu muốn
                        return;
                    }

                    let thumbnail = `https://res.cloudinary.com/dz5rz7doo/video/upload/so_3/${res.public_id}.jpg`;

                    if (thumb) {
                        try {
                            const tData = new FormData();
                            tData.append("file", thumb);
                            tData.append("upload_preset", "ml_default");

                            const thumbRes = await fetch("https://api.cloudinary.com/v1_1/dz5rz7doo/image/upload", {
                                method: "POST",
                                body: tData
                            });
                            const tJson = await thumbRes.json();
                            if (tJson.secure_url) thumbnail = tJson.secure_url;
                        } catch (err) {
                            console.error("❌ Upload thumbnail lỗi:", err);
                        }
                    }

                    const saveRes = await fetch("luu_video.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            ...info,
                            videoUrl: res.secure_url,
                            publicId: res.public_id,
                            duration: Math.floor(res.duration || 0),
                            thumbnail
                        })
                    });

                    const result = await saveRes.text();
                    console.log("📥 Server response:", result);
                    alert("📥 Server phản hồi:\n" + result);

                    // Gửi hoàn tất
                    window.opener.postMessage({
                        type: "done"
                    }, "*");
                    channel.postMessage({
                        type: "upload-finished"
                    });

                    if (result.includes("✅")) window.close();

                } catch (err) {
                    console.error("❌ Lỗi xử lý kết quả:", err);
                    alert("❌ Lỗi không xác định.");
                    window.opener.postMessage({
                        type: "error"
                    }, "*");
                    channel.postMessage({
                        type: "upload-finished"
                    });
                }
            };

            xhr.onerror = () => {
                alert("❌ Lỗi mạng khi upload video.");
                window.opener.postMessage({
                    type: "error"
                }, "*");
                channel.postMessage({
                    type: "upload-finished"
                });
            };

            xhr.send(formData);
        });
    </script>

</body>

</html>