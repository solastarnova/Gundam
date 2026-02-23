-- 初始資料（參考 Reference database/data.sql）
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

USE `mydb`;

TRUNCATE TABLE `order_items`;
TRUNCATE TABLE `orders`;
TRUNCATE TABLE `user_favorites`;
TRUNCATE TABLE `user_item`;
TRUNCATE TABLE `users`;
TRUNCATE TABLE `items`;

INSERT INTO `items` (`id`, `name`, `description`, `category`, `price`, `stock_quantity`, `image_path`) VALUES
    (1, '萬代模型 MG 144 獨角獸 正版高達模型', '高達模型，精緻還原動畫中的經典機體', 'RG', 248, 50, 'bandai-rg144-unicorn.jpg'),
    (2, 'BANDAI 高達模型 RG 144 00', 'BANDAI RG 144 00高達模型，雙爐系統設計，可動性優秀', 'RG', 126, 50, 'bandai-rg144-00.jpg'),
    (3, 'BANDAI 高達模型 RG 144 Freedom', 'BANDAI RG 144 Freedom自由高達模型，翅膀展開效果震撼', 'RG', 191, 50, 'bandai-rg144-freedom.jpg'),
    (4, 'BANDAI 高達模型 RG 144 SEED', 'BANDAI RG 144 SEED系列高達模型，經典機體重現', 'RG', 191, 50, 'bandai-rg144-seed.jpg'),
    (5, 'BANDAI 高達模型 RG 144 sinanju 新安州', 'BANDAI RG 144 sinanju新安州模型，紅色彗星再臨', 'RG', 291, 50, 'bandai-rg144-sinanju.jpg'),
    (6, 'BANDAI 高達模型 RG 144 wing gundam ew', 'BANDAI RG 144 wing gundam ew飛翼高達模型，EW版特殊設計', 'RG', 191, 50, 'bandai-rg144-wing.jpg');

INSERT INTO `users` (`id`, `name`, `email`, `password`) VALUES
    (1, 'abandon', 'abandon@gmail.com', '5214c805888ac0d11f6ba9852f681754');

INSERT INTO `user_item` (`id`, `user_id`, `item_id`, `quantity`, `status`, `review_title`, `review_content`, `review_rating`, `review_date`, `is_reviewed`, `date_time`) VALUES
    (1, 1, 1, 1, 'Completed', '分色與塗裝效果無敵！', '這款高達模型的分色設計簡直是業界標竿！板件預先分色完善，無需塗裝就能重現劇中標誌性的色彩搭配。細節處的獨立分件讓整體層次感更加豐富，完成後幾乎能達到官圖宣傳的完美效果。對於喜愛展示原創配色又不想費神塗裝的玩家來說，這絕對是最佳選擇！', 5, '2026-02-13 16:13:28', 1, '2026-02-13 16:13:28'),
    (2, 1, 2, 1, 'Completed', '高質體驗！', '本人係十分中意高達, 對於呢個產品我非常之鍾愛,能夠喺Gundam買到,唔使抽盲盒，實在係好開心，由落單、訂購、付款、去到送貨，成個流程都好流暢，而且好快就收到貨物，收到貨物發覺包裝亦都十分之好，務求令到商品到我手中嘅時候，10分妥善，成個購買流程，令我10分滿意，謝謝Gundam團隊，希望下次都可以喺你哋網站購物，謝謝你們！', 5, '2026-02-13 16:13:28', 1, '2026-02-13 16:13:28'),
    (3, 1, 3, 1, 'Completed', '品質絕對係正版！', '模型板材質優良，零件邊緣處理乾淨，幾乎無需修剪水口。組裝說明書清晰易懂，即使是新手也能輕鬆上手。完成後的模型質感高級，分量十足，價格合理，性價比超高。這不僅是模型，更是一件值得珍藏的藝術品！', 5, '2026-02-13 16:13:28', 1, '2026-02-13 16:13:28'),
    (4, 1, 4, 1, 'Completed', '體驗與細節完美！', '這款高達模型細節處理堪稱絕艷！每一處刻綫精準，關節靈活度超棒，可擺出超多帥氣戰鬥姿勢。分色細膩，貼紙貼合度高，組裝過程順暢又有趣，完成后擺在桌面上超有成就感，性價比超高，絕對是高達迷不容錯過的寶藏好物！', 5, '2026-02-13 16:24:30', 1, '2026-02-13 16:24:30'),
    (5, 1, 5, 1, 'Completed', '可玩度與體驗唔錯！', '高達模型的可動性超乎預期！關節設計緊實且靈活，能輕鬆擺出各種動態姿勢，無論是戰鬥場景還是經典站姿都穩定性十足。材質耐用，把玩時不用擔心輕易損壞，適合長時間展示和互動，完美結合了收藏價值與玩樂樂趣。', 5, '2026-02-13 16:24:30', 1, '2026-02-13 16:24:30'),
    (6, 1, 6, 1, 'Completed', '細節與設計完美！', '這款高達模型的細節處理真的令人驚艷！零件分件精細，刻線清晰，組裝過程非常流暢。整體設計還原了動畫中的經典造型，同時加入現代化的機械結構，讓模型看起來既帥氣又充滿科技感。完成後的成就感十足，絕對是鋼彈迷必收的佳作！', 5, '2026-02-13 16:24:30', 1, '2026-02-13 16:24:30');

-- user_favorites、orders、order_items 無預設資料，由程式寫入

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
