import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  results: any[] = [];
  loading = false;
 
  selectedReport = [];
  remark = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getDamageContainerIpqaLog();
  }
 
  viewFile(url) {
    url = this.service.url + '../../upload/damage/' + url +'?v=1';
   window.open(url, '_blank');
 }

 
  getDamageContainerIpqaLog() {
    this.loading = true;
    this.service.get('store/raw.php?type=getDamageContainerIpqaLog').subscribe(response => {
      this.results = Array.isArray(response) ? response : [];
      this.loading = false;
    }, () => {
      this.results = [];
      this.loading = false;
    });
  }
  
  damages =[];
  
  view(result) {
    this.selectedReport = result || {};
    const damageDetails = this.selectedReport['damage_details'];
    if (damageDetails && Array.isArray(damageDetails.containers)) {
      this.damages = damageDetails.containers;
    } else if (Array.isArray(damageDetails)) {
      this.damages = damageDetails;
    } else {
      this.damages = [];
    }
    this.isView = true;
  }
  
 
 
  
 
 
  
 
  
 
}
