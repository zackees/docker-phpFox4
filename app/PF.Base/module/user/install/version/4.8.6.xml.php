<upgrade>
    <crons>
        <cron>
            <module_id>user</module_id>
            <type_id>2</type_id>
            <every>1</every>
            <value><![CDATA[Phpfox::getService('user.verify.process')->deletePendingVerifications();]]></value>
            <name>Delete Pending Verification Users</name>
        </cron>
    </crons>
</upgrade>