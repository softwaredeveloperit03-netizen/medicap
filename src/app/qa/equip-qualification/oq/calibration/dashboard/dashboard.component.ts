import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  caliList=[];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
  }
  addData(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.caliList[this.caliList.length] = temp;
    data.resetForm();
  }

  delData(index) {
    this.caliList.splice(index, 1);
  }
  download(){
    this.service.open('');
   }
  //  save(form){
  //   if(!form.valid){
  //     alertify.error('All fields are required');
  //     return;
  //   }
  //   this.service.post('common.php?type=savereview', (form.value)).subscribe(response=>{
  //     if(response['status']==='success'){
  //       // this.router.navigate(['/hr/']);
  //       alertify.success('data save Successfuly');
  //       form.resetForm();
  //     }else{
  //       alertify.error('Error Occured');
  //     }
  //   });
  // } 
  save(data){
    if(data.valid)
    this.service.post('admin/housekeeping.php?type=savereview1',JSON.stringify(data.value)).subscribe(response=>{
      alert("saved succesfully")
      this.router.navigate(['/sales-force/doctor'])
      data.reset();
    });
    else{
      alert("All filled required");
    }
  }

  
}
