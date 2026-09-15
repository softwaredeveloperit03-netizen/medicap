import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  isNew = false;
  results;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getInprocessCapa();
  }

  getInprocessCapa() {
    this.service.get('qms/newIncident.php?type=getIncidentsLog&dep_name=' + localStorage.getItem('department')).subscribe(response => {
      this.results = response;
    });
  }

  selectedDev = [];
  viewCapa(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }

  viewFile1(url) {
    this.openIncidentDoc(url);
  }
  viewFile2(url) {
    this.openIncidentDoc(url);
  }
  viewFile3(url) {
    this.openIncidentDoc(url);
  }

  hasDoc(url): boolean {
    return !!(url && String(url).trim() && String(url).trim() !== 'NA');
  }

  openIncidentDoc(url): void {
    if (!this.hasDoc(url)) {
      alertify.error('Document not available');
      return;
    }
    window.open(this.service.url + '../../upload/incident/' + url, '_blank');
  }
}
