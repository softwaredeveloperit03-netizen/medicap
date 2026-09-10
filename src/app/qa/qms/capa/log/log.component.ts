import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
})
export class LogComponent implements OnInit {
  isView: boolean;
  selectedDev: any;
  results: any;
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getInprocessCapa();
  }
  getInprocessCapa() {
    this.service.get('qms/capa2.php?type=getLogCAPA').subscribe((response) => {
      this.results = response;
    });
  }
  viewCapa(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }
  viewFile1(url) {
    url = this.service.url + '../upload/incident/' + url;
    window.open(url, '_blank');
  }
  viewFile2(url) {
    url = this.service.url + '../../upload/incident/' + url;
    window.open(url, '_blank');
  }
  Download() {
    this.service.open(
      'qms/capa2.php?type=downloadCapaPdf&capa_no=' + this.selectedDev['capa_no']
    );
  }
}
