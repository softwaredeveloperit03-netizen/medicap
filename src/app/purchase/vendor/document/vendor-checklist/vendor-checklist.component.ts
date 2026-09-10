import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-vendor-checklist',
  templateUrl: './vendor-checklist.component.html',
  styleUrls: ['./vendor-checklist.component.css'],
})
export class VendorChecklistComponent implements OnInit {
  vendors: any;
  checklist_type;
  CheckList=[];
  constructor(private service: DataAccessService, private router: Router) {}
ngOnInit(): void {
     this.getChecklistHeading();
   }

   checklistHeadings;
   getChecklistHeading() {
    this.service.get('master/checklist.php?type=getchecklistHeading').subscribe((response) => {
        this.checklistHeadings = response;
      });
  }
 
  addData(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.CheckList[this.CheckList.length] = temp;
    data.resetForm();
  }

  delData(index) {
    this.CheckList.splice(index, 1);
  }

  chklist_id ='';

  saveChecklist(data) {
    console.log(data.value);


    if (!data.valid) {
      alert('All fields are required');
      return;
    }
   
    let temp = data.value;
    temp['CheckList'] = this.CheckList;

    this.service.post( 'master/checklist.php?type=saveCheckpoints&chklist_id='+this.chklist_id,JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alert('checklist Saved Successfully');
          this.CheckList = [];
           this.chklist_id ='';
           data.resetForm();
        } else {
          console.log(response);
          alert('Failed: An error occured, please try again!');
        }
      });
  }

  isDIGI = false;

  isAdd(value){

    if(value == 'ADD NEW'){
      this.isDIGI = true;
    } 
  }

  addchecklistHeading(data) {
     if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
 
    this.service.post('master/checklist.php?type=savChecHeadingg',JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alert('Saved Successfully');
          this.isDIGI = false;
          this.getChecklistHeading();
          this.chklist_id ='';
          data.resetForm();
        } else {
          console.log(response);
          alert('Failed: An error occured, please try again!');
        }
      });
  }






  // --------------------------------------------------------------------------------
  // checklistList = [];
  // lists= [];
  // departments;
  // evaluation_parameter;
  // checkListData: any;


  // ngOnInit(): void {
  //   this.getCheckListData();
  // }

  // getCheckListData(){

  //   this.service.get('master/checklist.php?type=getMastercheckList1').subscribe(response => {
  //     this.checkListData = response;
  //   });
  // }

  // addData(data) {
  //   if (!data.valid) {
  //     alert('All fields are required');
  //     return;
  //   }
  //   let temp = data.value;
  //   let tempData = [];
   
  //   this.checklistList[this.checklistList.length] = temp;
  //   console.log(this.checklistList);
  //   data.resetForm();
  // }

  // saveChecklist(data) {
  //   console.log(data.value);
  //   if (!data.valid) {
  //     alert('All fields are required');
  //     return;
  //   }
  //   let temp = data.value;
  //   temp['checklistList']=this.checklistList;

  //   this.service.post('master/checklist.php?type=SaveMastercheckPoint', JSON.stringify(temp)).subscribe(response => {
  //     if (response['status'] == 'success') {
  //       alert('checklist Saved Successfully');
  //       this.checklistList = [];
  //       this.router.navigate(['/checklist']);
  //     } else {
  //       console.log(response);
  //       alert('Failed: An error occured, please try again!');
  //     }
  //   });
  // }

  // delData(index) {
  //   this.checklistList.splice(index, 1);
  // }

  // del(index) {
  //   this.lists.splice(index, 1);
  // }

}


