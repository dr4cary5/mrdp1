FROM ubuntu:22.04

# 1. تنظیمات اولیه و بی‌صدا
ENV DEBIAN_FRONTEND=noninteractive

# 2. نصب همه ابزارهای لازم: محیط دسکتاپ، ریموت دسکتاپ و ابزارهای وب
RUN apt update && apt install -y --no-install-recommends \
    xfce4 \
    xfce4-goodies \
    xrdp \
    wget \
    curl \
    sudo \
    && rm -rf /var/lib/apt/lists/*

# 3. به xrdp می‌گیم از محیط دسکتاپ XFCE ما استفاده کنه
RUN echo "startxfce4" > /etc/xrdp/startwm.sh && \
    chmod +x /etc/xrdp/startwm.sh

# 4. یه کاربر جدید می‌سازیم که باهاش به ریموت وصل بشیم
RUN useradd -m githubuser && \
    echo "githubuser:YourStrongPassword123" | chpasswd && \
    usermod -aG sudo githubuser

# 5. یه اسکریپت کوچیک برای اینکه وقتی کانتینر روشن شد، سرویس ریموت هم روشن بشه
#RUN echo '#!/bin/bash \n\
#rm -f /var/run/xrdp/xrdp.pid \n\
#/etc/init.d/xrdp start \n\
#echo "RDP is ready" \n\
#tail -f /dev/null' > /start.sh && chmod +x /start.sh
# 5. راه‌انداز سرویس ریموت
RUN printf '#!/bin/bash\nrm -f /var/run/xrdp/xrdp.pid\n/etc/init.d/xrdp start\necho "RDP is ready"\ntail -f /dev/null\n' > /start.sh && chmod +x /start.sh

# 6. به داکر می‌گیم ما به این پورت‌ها دسترسی داریم (بعداً استفاده می‌شه)
EXPOSE 3389 8080

# 7. با اجرای کانتینر، اسکریپت ما اجرا بشه
CMD ["/start.sh"]
