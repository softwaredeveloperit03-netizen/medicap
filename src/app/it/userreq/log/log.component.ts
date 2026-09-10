  import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {



   isView = false;
   results;



    constructor(private service:DataAccessService) { }


    ngOnInit(): void {

      this.getUserReqLog();

    }

    getUserReqLog() {
        this.service.get('it/it.php?type=getUserReqLog&dept='+localStorage.getItem('department')).subscribe((response: any) => {
        this.results = response;
      });
    }


    selectedResult = [];
    view(index){
      this.isView =  true ;
      this.selectedResult = this.results[index];
    }


    getYesNo(value){
      if(value == 1){
        return "Yes" ;
      }else{
        return "No" ;
      }
    }

    getReqData(data) {
      let returnString = '';
      if (data['activation'] == 1) {
        returnString += "Activation, ";
      }
      if (data['deactivation'] == 1) {
        returnString += "Deactivation, ";
      }
      if (data['modification'] == 1) {
        returnString += "Modification, ";
      }
      if (data['other'] == 1) {
        returnString += "Others, ";
      }
      if (data['appFacility'] == 1) {
        returnString += "Application Facility (MS Office), ";
      }
      if (data['email'] == 1) {
        returnString += "Email, ";
      }
      if (data['internet'] == 1) {
        returnString += "Internet Access.";
      }

      // Optional: Remove trailing comma/space if it ends with one
      return returnString.trim().replace(/,\s*$/, '');
    }



  remark = '';


    allotIpID(data){

      if(!data.valid){
        alertify.error("All FIel Required !!!!!!!!");
        return;
      }

      let temp = data.value;
      temp['id']  =  this.selectedResult['id'];

      this.service.post('it/it.php?type=allotIpID', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
          alert('Updated Successfully');
          this.getUserReqLog()
          this.isView = false;
          data.reset();

          } else {
          alert('Failed: An error occured, please try again!');
        }
      });
    }

  }
