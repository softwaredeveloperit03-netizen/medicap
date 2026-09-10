import { Component, OnInit } from '@angular/core';
 import { ActivatedRoute } from '@angular/router';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-ooscheck',
  templateUrl: './ooscheck.component.html',
  styleUrls: ['./ooscheck.component.css']
})
export class OoscheckComponent implements OnInit {

  checklistList = [];
  evaluation_parameter;
  checklist_heading ='';
  ooschecklist_data;
  check_index;
  
  constructor(private service: DataAccessService, public route: ActivatedRoute, private router: Router) { }

  ngOnInit(): void {
   this.getooschecklist();
   }

   getooschecklist() {
    this.ooschecklist_data =[];
    this.service.get('master/checklist.php?type=getooschecklistmaster').subscribe(response => {
      this.ooschecklist_data = response;
    });
  }

  addData(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['checklist_heading'] = this.checklist_heading;
    temp['anaylyst'] = '';
    temp['riviewer'] = '';
    
   
    this.checklistList[this.checklistList.length] = temp;
    console.log(this.checklistList);
    data.resetForm();
  }

  saveChecklist(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['checklistList']=this.checklistList;

    this.service.post('master/checklist.php?type=oos_checklist', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('checklist Saved Successfully');
        this.checklistList = [];
        this.checklist_heading ='';
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
