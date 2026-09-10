import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-oos',
  templateUrl: './oos.component.html',
  styleUrls: ['./oos.component.css']
})
export class OosComponent implements OnInit {


  oos_data ;
  selectedOos;
  isView = false;

  ooschecklist_data;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingoos();
    this.QcEmployee1();
    }

  getPendingoos() {
    this.service.get('qc/oos.php?type=getinviestigatingoos').subscribe(response => {
      this.oos_data = response;
    });
  }

  getooschecklist() {
    this.service.get('master/checklist.php?type=getooschecklis_for_investing').subscribe(response => {
      this.ooschecklist_data = response;
    });
  }

  QCEmployee;
  QcEmployee1() {
    
    this.service.get('master/checklist.php?type=getapprovedEmployee&department1=Quality Control').subscribe(response => {
      this.QCEmployee = response;
    });
  }


  viewoos(index){
    this.isView = true;

    this.selectedOos = this.oos_data[index];
    this.getooschecklist();
  }

  ClosedOOS;


  jadugarFunction(){

    
    this.ClosedOOS = this.selectedOos['oos_data'].concat(this.ooschecklist_data);

    console.log(this.ClosedOOS);

  }




  sendchecking(){

    console.log("this.oos_data");
    console.log( this.selectedOos['oos_data']);
    console.log("this.ooschecklist_data");
    console.log( this.ooschecklist_data);


    this.jadugarFunction();




  this.selectedOos['ClosedOOS'] = this.ClosedOOS;

        
    this.service.post('qc/oos.php?type=ooschecking&oosid='+this.selectedOos['id'], JSON.stringify(this.selectedOos)).subscribe(response => {
      if (response['status'] == "success") {
         this.isView = false;
         alertify.success("submit sucessfully");
        this.getPendingoos();
      } else {
        alertify.error('An error occured');
      }
    });

  }

  sendreview(){

    console.log("this.oos_data");
    console.log( this.selectedOos['oos_data']);
    console.log("this.ooschecklist_data");
    console.log( this.ooschecklist_data);


    this.jadugarFunction();




  this.selectedOos['ClosedOOS'] = this.ClosedOOS;

        
    this.service.post('qc/oos.php?type=oosreview&oosid='+this.selectedOos['id'], JSON.stringify(this.selectedOos)).subscribe(response => {
      if (response['status'] == "success") {
         this.isView = false;
         alertify.success("submit sucessfully");
        this.getPendingoos();
      } else {
        alertify.error('An error occured');
      }
    });

  }
  

 
  isdeffbtn =false;

  furtherdiss(mainIndex, subIndex, value){

    console.log(mainIndex);
    console.log(subIndex);
    console.log(value);
 
    if(value == 'NO'){
      this.isdeffbtn = true;
      
      try {
          if (this.ooschecklist_data[mainIndex] && Array.isArray(this.ooschecklist_data[mainIndex].check_points)) {
            this.ooschecklist_data[mainIndex].check_points = this.ooschecklist_data[mainIndex].check_points.slice(0, subIndex);
          } else {
            throw new Error("Invalid mainIndex or check_points is not an array.");
          }
          if (mainIndex >= 0 && mainIndex < this.ooschecklist_data.length) {
            mainIndex = mainIndex + 1;
            this.ooschecklist_data = this.ooschecklist_data.slice(0, mainIndex);
          } else {
            throw new Error("Invalid mainIndex.");
          }
      } catch (error) {
          console.error("An error occurred:", error);
      }
    }else{
      
    }



  console.log( this.ooschecklist_data);

}

 

}
