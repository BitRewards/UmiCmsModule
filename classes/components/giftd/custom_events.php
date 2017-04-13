<?
new umiEventListener("order_refresh", "giftd", "applyGiftd");
new umiEventListener("order-status-changed", "giftd", "processGiftd");
new umiEventListener("order-status-changed", "giftd", "updateOrderGiftd");
new umiEventListener("systemBufferSend", "giftd", "loadPage");
