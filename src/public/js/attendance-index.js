// 打刻画面の現在日時をリアルタイム表示
document.addEventListener('DOMContentLoaded', function () {
    const dateElement = document.getElementById('current-date');
    const timeElement = document.getElementById('current-time');

    if (!dateElement || !timeElement) {
        return;
    }

    const weekDays = ['日', '月', '火', '水', '木', '金', '土'];

    // 2桁ゼロ埋め
    function pad(value) {
        return String(value).padStart(2, '0');
    }

    // 日付と時刻を更新
    function updateDateTime() {
        const now = new Date();

        const year = now.getFullYear();
        const month = now.getMonth() + 1;
        const day = now.getDate();
        const weekDay = weekDays[now.getDay()];

        const hours = pad(now.getHours());
        const minutes = pad(now.getMinutes());

        dateElement.textContent = `${year}年${month}月${day}日（${weekDay}）`;
        dateElement.setAttribute('datetime', `${year}-${pad(month)}-${pad(day)}`);

        timeElement.textContent = `${hours}:${minutes}`;
        timeElement.setAttribute('datetime', `${hours}:${minutes}`);
    }

    // 初回表示
    updateDateTime();

    // 次の分ちょうどまで待って、その後は1分ごとに更新
    const now = new Date();
    const delay = (60 - now.getSeconds()) * 1000 - now.getMilliseconds();

    setTimeout(function () {
        updateDateTime();
        setInterval(updateDateTime, 60000);
    }, delay);
});