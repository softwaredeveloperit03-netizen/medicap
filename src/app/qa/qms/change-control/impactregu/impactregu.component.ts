import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
@Component({
  selector: 'app-impactregu',
  templateUrl: './impactregu.component.html',
  styleUrls: ['./impactregu.component.css']
})
export class ImpactreguComponent implements OnInit {
  
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getCcForImpactReguAndMArkAuth();
  }


  typeCritical = '';
  typeMajor = '';

  results;
  isView = false;

  getCcForImpactReguAndMArkAuth() {
    this.service.get('changecontrol1.php?type=getCcForImpactReguAndMArkAuth&deptName='+localStorage.getItem('department')).subscribe((response) => {
        this.results = response;
      });
  }
  selectedResult = [];

  view(i){

    this.selectedResult = this.results[i];
    this.isView = true;
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
 

   temp['id'] = this.selectedResult['id'];
  temp['ccNo'] = this.selectedResult['ctrl_no'];
  temp['deptName'] = localStorage.getItem('department');
   
  this.service.post('changecontrol1.php?type=SaveImpactOnReguAffaiAndMarketing', JSON.stringify(temp)).subscribe(
      (response) => {
        if (response['status'] === 'success') {
          alert('Saved Successfully !!!!!!');
          this.getCcForImpactReguAndMArkAuth();
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
