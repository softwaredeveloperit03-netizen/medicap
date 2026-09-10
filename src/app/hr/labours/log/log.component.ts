import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  labour_name='';
  results;
  address;
  labourlist;
  labours = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getLabours();
    this.getlabour();
  }

  getLabours() {
    this.service.get('hr/labour.php?type=getLaboursLog').subscribe((response : any) => {
      this.results = response;
      this.filterLabour();
    });
  }
  getlabour() {
    this.service.get('hrDepartment.php?type=getlabourlist').subscribe((response:any) => {
      this.labourlist = response;
    });
  }

  open(url) {
    url = this.service.url + '../../upload/contractor/labour/' + url;
    window.open(url, '_blank');
  }


  filterLabour() {
    this.labours = [];
    for (let i = 0; i < this.results.length; i++) {
      let material = this.results[i];
      if (material['labour_name'].toUpperCase().includes(this.labour_name.toUpperCase())) {
        this.labours[this.labours.length] = material;
      }
    }
  }
  downloadReport(){ 
    this.service.open('hrDepartment.php?type=labour_list_log_pdf');
  }
}

