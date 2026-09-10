import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-repairdesc',
  templateUrl: './repairdesc.component.html',
  styleUrls: ['./repairdesc.component.css']
})
export class RepairdescComponent implements OnInit {

  isView = false;
  isViewData = false;
  results;
  equipments;
  employees;
  preApproval = '';
  prili_req = 'NO';

  selectedResult = [];
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getEmployee();
    this.getbreakdown_data();
  }

  getEmployee(){
    this.service.get('engineering/maintenance.php?type=getEmployee').subscribe(response => {
      this.employees = response;
    });
  }
 
 
  getbreakdown_data(){
    this.service.get('engineering/maintenance.php?type=getdescription_of_repair').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }
 

 

  addrepairDescription(data) {

    if(!data.valid){
      alertify.error("All fields are required");
      return;
    }

    let temp =data.value; 
 
    this.service.post('engineering/maintenance.php?type=updaterepairDescription&id='+this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.isView = false;
        this.getbreakdown_data();
        data.reset();
        
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }



  

}
