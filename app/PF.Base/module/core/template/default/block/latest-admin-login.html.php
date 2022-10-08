<table class="table table-hover admin-logins-container">
    <thead>
    <tr>
        <th>{_p var='admin'}</th>
        <th>{_p var='last_login'}</th>
    </tr>
    </thead>
    <tbody>
    {foreach from=$aLastAdmins name=lastadmins item=aLastAdmin}
    <tr>
        <td>
            {$aLastAdmin|user}
            <br/>
            <div>{$aLastAdmin.ip_address}</div>
        </td>
        <td>{$aLastAdmin.time_stamp|date:'core.extended_global_time_stamp'}</td>
    </tr>
    {/foreach}
    </tbody>
</table>