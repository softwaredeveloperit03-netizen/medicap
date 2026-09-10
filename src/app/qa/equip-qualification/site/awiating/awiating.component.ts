import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-awiating',
  templateUrl: './awiating.component.html',
  styleUrls: ['./awiating.component.css']
})
export class AwiatingComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingSites();
  }

  getPendingSites(){
    this.service.get('qa/qualification.php?type=getPendingSites').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }


  ViewCertificate() {
    window.open(this.service.url+'../../upload/dq/' + this.selectedResult['dq_file']);
  }

  UpdateSite(status){
    this.service.get('qa/qualification.php?type=saveSite&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data updated successfully!');
        this.isView = false;
        this.getPendingSites();
      }else{
        alertify.error('Failed an error occured,Please try again!');
      }
    });
  }

}
