<div class="container mx-auto mt-5 pt-2">
    <div class="accordion" id="accordionExample">
        <h2 class="mt-2 mb-2 text-info">Shopping & Service FAQ</h2>
        <?php
        $accordionItems = [
            [
                'id' => 'One',
                'title' => '1. My parcel looks damaged.',
                'content' => 'Please contact us as soon as possible with clear photos of the damaged area and shipping label. Keep all packaging materials until the issue is resolved.',
                'show' => true
            ],
            [
                'id' => 'Two',
                'title' => '2. An item or part is damaged.',
                'content' => 'Please contact us within 7 days after delivery and provide details plus photos. Keep the shipping box, label, and all package contents for verification.',
                'show' => false
            ],
            [
                'id' => 'Three',
                'title' => '3. Product differs from website photos.',
                'content' => 'Some photos may be prototypes or manufacturer samples. Minor paint or assembly differences can occur in mass production.',
                'show' => false
            ],
            [
                'id' => 'Four',
                'title' => '4. Exchange request period',
                'content' => 'Exchange requests must be submitted within 7 days from delivery date, and completed within 14 days after approval.',
                'show' => false
            ],
            [
                'id' => 'Five',
                'title' => '5. Exchange eligibility',
                'content' => 'Items must be purchased from our store, unopened, complete, and in resalable condition.',
                'show' => false
            ],
            [
                'id' => 'Six',
                'title' => '6. QC-related exchange policy',
                'content' => 'QC exchange availability depends on each brand/distributor policy. Please contact customer service for brand-specific details.',
                'show' => false
            ],
            [
                'id' => 'Seven',
                'title' => '7. Refund policy',
                'content' => 'Refunds are usually returned to the original payment method. Processing time depends on payment channel and banking partner.',
                'show' => false
            ],
        ];

        foreach ($accordionItems as $item) {
            $collapseClass = $item['show'] ? 'show' : '';
            $buttonClass = $item['show'] ? '' : 'collapsed';
            $expanded = $item['show'] ? 'true' : 'false';
            ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading<?= $item['id'] ?>">
                    <button class="accordion-button <?= $buttonClass ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $item['id'] ?>" aria-expanded="<?= $expanded ?>" aria-controls="collapse<?= $item['id'] ?>">
                        <?= $item['title'] ?>
                    </button>
                </h2>
                <div id="collapse<?= $item['id'] ?>" class="accordion-collapse collapse <?= $collapseClass ?>" aria-labelledby="heading<?= $item['id'] ?>" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <?= $item['content'] ?>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
