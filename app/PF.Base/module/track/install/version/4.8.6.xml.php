<upgrade>
    <crons>
        <cron>
            <module_id>track</module_id>
            <type_id>3</type_id>
            <every>1</every>
            <value><![CDATA[Phpfox::getService('track.process')->cleanExpiredTracker();]]></value>
            <name>Remove Expired Tracked Data</name>
        </cron>
    </crons>
</upgrade>