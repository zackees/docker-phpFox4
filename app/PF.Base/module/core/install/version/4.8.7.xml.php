<upgrade>
    <crons>
        <cron>
            <module_id>core</module_id>
            <type_id>1</type_id>
            <every>5</every>
            <value><![CDATA[Phpfox::getService('core.schedule')->addQueueScheduleItems();]]></value>
            <name>Add Queue Schedule Items</name>
        </cron>
    </crons>
</upgrade>