import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  constructor(private service: DataAccessService) {}

  utilityList:any = []; // Initialize utility list
 
  

  ngOnInit(): void {
     this.getChecklist();
  }
 getChecklist(){
  this.service.get('engineering/qualification.php?type=getUtilityFunctions')
  .subscribe(response => { 
    this.utilityList=response;


    });
}
  addData(data) {
   let temp=data.value;
    
    this.service.post('engineering/qualification.php?type=saveUtilityFunctions',JSON.stringify(temp))
    .subscribe(response => {
      if(response['status'] === 'success') {
        alertify.success('success');
        data.resetForm();
        this.getChecklist()
        
        
      } else {
        alertify.error(response['status']);
      }
      });
  }

  delData(id) {
     this.service.post('engineering/qualification.php?type=deleteData&id='+id+'&Table=UtilityFunctions',null)
    .subscribe(response => {
      if(response['status'] === 'success') {
        alertify.success('success');
       
        this.getChecklist()
        
        
      } else {
        alertify.error(response['status']);
      }
      });
  }
  download(){
    this.service.open('engineering/qualification.php?type=DownloadUtilityFunctions');
   }
   
  // addData(data) {
  //   if (!data.valid) {
  //     alertify.error('All fields are required!');
  //     return;
  //   }
  //   const temp = data.value;

  //   // Check for duplicate entries
  //   if (
  //     this.utilityList.some(
  //       (item) =>
  //         item.utility === temp.utility &&
  //         item.fun_ass === temp.fun_ass &&
  //         item.actual_obs === temp.actual_obs &&
  //         item.remark === temp.remark
  //     )
  //   ) {
  //     alertify.error('Duplicate entry is not allowed!');
  //     return;
  //   }

  //   // Add new data to the utility list
  //   this.utilityList.push(temp);
  //   alertify.success('Data added successfully!');
  //   data.resetForm(); // Reset the form after adding
  // }

  // delData(index: number) {
  //   this.utilityList.splice(index, 1);
  //   alertify.success('Data deleted successfully!');
  // }

   
}
