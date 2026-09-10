import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css']
})
export class HomeComponent implements OnInit {

  specifications;
   
  isView = false;
  isCC = false;
  isRivision = false;
  
  selectedSpec = [];

   constructor(private service: DataAccessService) { }

  ngOnInit() {
     this.getReports();
  }
   
  
  getReports() {
    this.service.get('qc/specification/inprocess.php?type=getSpecificationsLog').subscribe(response => {
      this.specifications = response;
    });
  }
 
  viewSpecification(index) {
    this.isView = true;
    this.selectedSpec = this.specifications[index];
  }
   

  CahngeControl(index){
    this.isCC = true;
    this.selectedSpec = this.specifications[index];
  }

  rivision(index){
    this.isRivision = true;
    this.selectedSpec = this.specifications[index];
  }





  send_revision(data){
    let temp=data.value
    temp["doc_name"]=this.selectedSpec['product_code']
    temp["doc_no"]=this.selectedSpec['specification_no']
    temp["revison_from"]= 'Specification' ;
    
    console.log(temp);
    this.service.post('master/test.php?type=save_Request_specification', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isRivision = false;
        this.getReports();
      
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
    
    }












}
