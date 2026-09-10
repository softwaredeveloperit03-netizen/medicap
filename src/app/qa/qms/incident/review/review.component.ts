import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-review',
  templateUrl: './review.component.html',
  styleUrls: ['./review.component.css']
})
export class ReviewComponent implements OnInit {
  isView = false;
  isNew = false;
  results;
 
  
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getInprocessCapa(); 
  }
  
  
 
  getInprocessCapa(){
    this.service.get('qms/newIncident.php?type=getPendingIncidents&dep_name='+localStorage.getItem('department')).subscribe(response => {
      this.results = response;
    });
  }
  selectedDev=[];
  viewCapa(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }
 
 
 
    viewFile1(url) {
    url = this.service.url + '../../upload/incident/' + url;
    window.open(url, '_blank');
  }
  viewFile2(url) {
    url = this.service.url + '../../upload/incident/' + url;
    window.open(url, '_blank');
  }
  viewFile3(url) {
    url = this.service.url + '../../upload/incident/' + url;
    window.open(url, '_blank');
  }




  save(status) {
    let temp={};

    this.service.post('qms/newIncident.php?type=incidentChecking&status='+status+'&id='+this.selectedDev['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('successfull');
        this.isView=false;
        this.getInprocessCapa(); 
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  
}
