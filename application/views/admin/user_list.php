<div class="page-wrapper">
<div class="page-content">

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
<div class="breadcrumb-title pe-3">Table</div>

<div class="ps-3">
<nav>
<ol class="breadcrumb mb-0 p-0">
<li class="breadcrumb-item">
<a href="<?= base_url('dashboard');?>"><i class="bx bx-home-alt"></i></a>
</li>
<li class="breadcrumb-item active">Users</li>
</ol>
</nav>
</div>
</div>

<hr>

<div class="card">
<div class="card-body">

<div class="d-lg-flex align-items-center mb-4 gap-3">
<input type="text" id="search" class="form-control w-25" placeholder="Search user...">
</div>

<div class="table-responsive">
<table class="table mb-0">

<thead class="table-light">
<tr>
<th>Index#</th>
<th>Name</th>
<th>Mobile</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>

<tbody id="users"></tbody>

</table>
</div>

</div>
</div>

</div>
</div>

<script src="<?= base_url('assets/js/jquery.min.js')?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

const site_url="<?= base_url()?>";

function loadUsers(search=''){

$.ajax({
url: site_url+"index.php/admin/user/fetch_users",
type:"POST",
data:{search:search},
dataType:"json",

success:function(res){

let html='';

if(!res.data.length){
$("#users").html('<tr><td colspan="5" class="text-center">No users found</td></tr>');
return;
}

res.data.forEach((u,i)=>{

html+=`
<tr>

<td>${i+1}</td>

<td>${u.name}</td>

<td>${u.mobile}</td>

<td>
${u.isActive==1
? `<div class="d-flex align-items-center text-success">
<i class="bx bx-radio-circle-marked bx-burst bx-rotate-90 font-18 me-1"></i>
<span>Active</span></div>`
: `<div class="d-flex align-items-center text-danger">
<i class="bx bx-radio-circle-marked bx-burst bx-rotate-90 font-18 me-1"></i>
<span>Inactive</span></div>`
}
</td>

<td>
<div class="d-flex order-actions align-items-center">

<a href="#" class="me-2">
<i class="bx bxs-edit"></i>
</a>

${u.isActive==1
? `<a href="javascript:;" class="toggle-status-btn text-danger ms-2"
data-id="${u.id}" data-status="0">
<i class="bx bxs-hide fs-5"></i></a>`
: `<a href="javascript:;" class="toggle-status-btn text-success ms-2"
data-id="${u.id}" data-status="1">
<i class="bx bxs-show fs-5"></i></a>`
}

</div>
</td>

</tr>`;
});

$("#users").html(html);

}

});

}

loadUsers();

$("#search").keyup(function(){
loadUsers($(this).val());
});

$(document).on("click",".toggle-status-btn",function(){

let id=$(this).data("id");
let status=$(this).data("status");

$.post(site_url+"index.php/admin/user/toggle_status",{id:id,status:status},function(){

Swal.fire({
icon:'success',
title:'Status Updated',
timer:1500,
showConfirmButton:false
});

setTimeout(()=>loadUsers($("#search").val()),1500);

});

});

</script>
