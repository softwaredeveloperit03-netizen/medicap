import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
   declare let alertify;

@Component({
  selector: 'app-fgreport',
  templateUrl: './fgreport.component.html',
  styleUrls: ['./fgreport.component.css']
})
export class FgreportComponent implements OnInit {

    plant_id = localStorage.getItem('plant_id');
    isView = false;

    constructor(private service:DataAccessService) {}

    ngOnInit(): void {
      this.plant_id = localStorage.getItem('plant_id');
      this.getApprovedSPecificationByTypeAndStatus();
    }

    results;
    spec_type = 'Raw Material';
    getApprovedSPecificationByTypeAndStatus(){
      this.service.get('qc/specification/raw.php?type=getApprovedSPecificationByTypeAndStatus&status=Approved&spec_type=Finish Product').subscribe(response => {
        this.results = response;
      });
    }

    approvalRemark = '';
    updateSpecification(status) {

      if(this.approvalRemark == ''){
        alertify.error('Please Add Remark.....');
        return;
      }

      let temp = {};
      temp['status'] = status;
      temp['id'] = this.selectedResult['id'];
      temp['approvalRemark'] = this.approvalRemark;

      this.service.post('qc/specification/raw.php?type=approveSpecification',JSON.stringify(temp)).subscribe(response => {
        if(response['status'] == 'success'){
          alertify.success('Specification '+status+' Successfully');
          this.getApprovedSPecificationByTypeAndStatus();
          this.isView = false;
        }else{
          alertify.error('Failed: An error occured, please try again!');
        }

      });

    }

    selectedResult = [];
    view(data) {
      this.selectedResult = data;
      this.isView = true;
    }




  searchQuery;
 
   get filteredMaterials(): any[] {
     if (!this.searchQuery || this.searchQuery.trim() === '') {
       return this.results; // If search query is empty or whitespace, return all materials
     }
 
     const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
 
     return this.results.filter((material) => {
       // Check if any field of the material contains the search query
       return Object.entries(material).some(([key, value]) => {
         if (key === 'entry_date') {
           // Convert the value to a Date object if it's not already
           const dateValue = typeof value === 'string' ? new Date(value) : value;
           // Check if the date value is valid and includes the search query
           return (
             dateValue instanceof Date &&
             dateValue.toISOString().slice(0, 10).includes(query)
           );
         } else {
           // Convert field value to lowercase and check if it includes the search query
           return value && value.toString().toLowerCase().includes(query);
         }
       });
     });
   }



  isShow1 = true;
  toggleShow1(){
    this.isShow1 = !this.isShow1;
  }


  }
