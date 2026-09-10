import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-closinchecklist',
  templateUrl: './closinchecklist.component.html',
  styleUrls: ['./closinchecklist.component.css']
})
export class ClosinchecklistComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getCcForClosinChecklist();
  }
 
  closinChecklist = [
    { action: "Whether all applicable documents are revised, distributed, and implemented.", checkPoint: "", type: "1"  },
    { action: "All the superseded documents are retrieved, made obsolete, and destroyed.", checkPoint: "", type: "1"  },
    { action: "The change is implemented as proposed.", checkPoint: "" , type: "1" },
    { action: "Training is completed and evaluated to all as recommended.", checkPoint: "", type: "1"  },
    { action: "The qualification/validation/stability as applicable is initiated.", checkPoint: "", type: "1"  },
    { action: "Others. Specify.", checkPoint: "" , type: "1" },
    { action: "After three extension TO/ATCD, if not closed within 90 days, new CCF shall be initiated by concerned.", checkPoint: "" , type: "1" },
    { action: "CAPA No. (if any)", checkPoint: "" , type: "3" },
    { action: "Change Control Implementation Status and Closer.", checkPoint: "", type: "2"  },
    { action: "Change effective from Product Name/Doc. Name.", checkPoint: "", type: "1"  },
    { action: "Change Effective From Medicap Lot No/Equip./Instru.No.", checkPoint: "" , type: "3" },
    { action: "Change Effective From Document No.", checkPoint: "" , type: "3" },
    { action: "Change Control Closure Date", checkPoint: "" , type: "4" },
  ];
 
  typeCritical = '';
  typeMajor = '';

  results;
  isView = false;

  getCcForClosinChecklist() {
    this.service.get('changecontrol1.php?type=getCcForClosinChecklist&deptName='+localStorage.getItem('department')).subscribe((response) => {
        this.results = response;
      });
  }
  selectedResult = [];

  view(i){

    this.selectedResult = this.results[i];
    this.isView = true;
  }



  checlistData =[];

  addAction(data){
    if(!data.valid){
      alertify.error('All Field Required!!!!');
      return;
    }

    let temp = data.value;
    this.checlistData.push(temp);
    data.reset();
   }

   delAction(i){
    this.checlistData.splice(i,1);
   }

 
  viewDevDoc(url) {
    url = this.service.url + '../../upload/changeControl/' + url;
   window.open(url, '_blank');
  }
 


  consentRevDoc: File;
  onFileChanged(event) {
   if (event.target.files.length === 1) {
     this.consentRevDoc = event.target.files[0];
   }
 }



 update(data) {

  if (!data.valid) {
    alert('All fields are required');
    return;
  }

  const temp = data.value;

  temp['closinChecklist'] = this.closinChecklist; 

 
  temp['id'] = this.selectedResult['id']; 
  temp['ccNo'] = this.selectedResult['ctrl_no'];
  temp['deptName'] = localStorage.getItem('department');
   
  this.service.post('changecontrol1.php?type=saveCcForClosinChecklist', JSON.stringify(temp)).subscribe(
      (response) => {
        if (response['status'] === 'success') {
          alert('Saved Successfully !!!!!!');
          this.getCcForClosinChecklist();
          data.resetForm();
          this.isView = false;
          this.selectedResult =[];
        } else {
          alert('Failed: An error occurred, please try again!');
        }
      }
    );
}
 

}
