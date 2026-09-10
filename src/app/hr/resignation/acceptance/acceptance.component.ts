import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-acceptance',
  templateUrl: './acceptance.component.html',
  styleUrls: ['./acceptance.component.css']
})
export class AcceptanceComponent implements OnInit {


 checklistList = [];
 checkListData: any;
  
  constructor(private service: DataAccessService,  private router: Router) { }

  ngOnInit(): void {
    this.getCheckListData();
  }

  getCheckListData(){
    this.service.get('master/checklist.php?type=getExitInterviewcheckList').subscribe(response => {
      this.checkListData = response;
    });
  }

  addData(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    let tempData = [];
   
    this.checklistList[this.checklistList.length] = temp;
    console.log(this.checklistList);
    data.resetForm();
  }

  saveChecklist( ) {
    
    if (this.checklistList.length == 0) {
      alert('Please Add Exit Interview Checklist');
      return;
    }

    let temp = {};
    temp['checklistList']=this.checklistList;

    this.service.post('master/checklist.php?type=saveExitChecklist', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('checklist Saved Successfully');
        this.checklistList = [];
        this.router.navigate(['/checklist']);
      } else {
        console.log(response);
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  delData(index) {
    this.checklistList.splice(index, 1);
  }

 

}