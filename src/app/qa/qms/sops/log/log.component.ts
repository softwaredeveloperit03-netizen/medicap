import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  constructor(private service: DataAccessService) {  }

  ngOnInit(): void {
    this.getSopLog(localStorage.getItem('department'));
  }

  result;
  isView = false;
  selectedResult;

  getSopLog(department) {
    this.service.get('sops.php?type=getSopLog&deptName='+department).subscribe((response: any) => {
      this.result = response;
     });
  }

   

  viewFile(url) {
    url = this.service.url + '../../upload/Sops/' + url+'?v=1';
   window.open(url, '_blank');
 }



 reviewSop(){

let temp ={};

  this.service.post('sops.php?type=saveReviewedByQa&iniId='+this.selectedResult['iniId']+'&sopsId='+this.selectedResult['sopsId'], JSON.stringify(temp)).subscribe(
    (response) => {
      if (response['status'] === 'success') {
        alert('Reviewed Successfully !!!!!!');
        this.getSopLog(localStorage.getItem('department'));
         this.isView = false;
      } else {
        alert('Failed: An error occurred, please try again!');
      }
    }
  );

 }




}
