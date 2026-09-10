import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-self-certificate',
  templateUrl: './self-certificate.component.html',
  styleUrls: ['./self-certificate.component.css']
})
export class SelfCertificateComponent implements OnInit {

  reports;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getSelfCertificates();
  }

  getSelfCertificates() {
    this.service.get('training.php?type=getSelfCertificates').subscribe(response => {
      this.reports = response;
    });
  }
  downloadreport(){
    this.service.open('pdf1/training.php?type=selfcertificatereport');
  }
}
