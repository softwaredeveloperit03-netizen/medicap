import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  updatelabs;
  selectresult =[];
  isView = false;

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getLabsLog();
  }

  viewCertifidate(){
    let url = this.service.url+this.selectresult['certificate'] ;
    window.open(url, '_blank');
  }

  viewLic(){
    let url = this.service.url+this.selectresult['lic'] ;
    window.open(url, '_blank');
  }

  getLabsLog(){
    this.service.get('qc/lab.php?type=getLabsLog').subscribe(response =>{
      this.updatelabs = response;
    });

  }
  
  download(){
    this.service.open('qc/lab.php?type=downloadLabsLog')
  }

  view(index){
    this.selectresult =this.updatelabs[index];
      this.isView = true;
}

}
