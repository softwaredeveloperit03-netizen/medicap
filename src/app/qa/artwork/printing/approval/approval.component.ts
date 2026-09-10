import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];
  emp_id: string;
  isDIGI: boolean;
  status: any;

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getArtworksLog();
  }
  getArtworksLog(){
    this.service.get('qa/artwork.php?type=getUploadedPrintings').subscribe(response=>{
      this.results=response;
    })
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  opendoc(url){
    url=this.service.url;
    window.open(url+this.selectedResult['file']);
     //this.service.open('qa/artwork.php=getArtworksLog&id='+this.selectedResult['id']);
  }
  openFile(url){
    url=this.service.url;
    window.open(url+this.selectedResult['design_file']);
  }
  printingFile(url){
    url=this.service.url;
    window.open(url+this.selectedResult['printing_file']);
  }
  update(status) {
    this.service.get('qa/artwork.php?type=approvePrinting&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record updated successfully');
        this.isView = false;
        this.getArtworksLog();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  openDigiSign(value) {
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.status = value
  }

  loginPassward = '';
  digiSign(data) {

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }

    this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.loginPassward + '&emp_id=' + this.emp_id).subscribe(response => {
      if (response['status'] == 'success') {
      alertify.success('Digi-Sign Verified successfully');
        this.isDIGI = false;
        this.loginPassward = '';
        this.update(this.status);
      }
      else {
     alertify.error('Digi-Sign Not Verified');

      }
    });
  }


}
