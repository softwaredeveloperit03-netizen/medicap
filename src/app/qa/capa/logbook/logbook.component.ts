import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-logbook',
  templateUrl: './logbook.component.html',
  providers: [DatePipe]
})
export class LogbookComponent implements OnInit {

  isView = false;
  results = [];
  fromdate = '';
  todate = '';

  selectedResult = [];
  constructor(private datePipe: DatePipe,private service: DataAccessService) {
    this.todate = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getChangeControls();
  }

  getChangeControls() {
    this.service.get('qa/capa.php?type=getcapalog&fromdate='+this.fromdate+'&todate='+this.todate).subscribe((response:any) => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  getprint(){
    this.service.open('pdf1/capa.php?type=capalog&fromdate='+this.fromdate+'&todate='+this.todate);
  }

  viewfile(url) {
    window.open(this.service.url + url, '_blank');
  }
  printcapa(capa_no, type){
    if(type == 'manual'){
      this.service.open('pdf1/capa.php?type=capa&capa_no='+capa_no);
    }else{
      this.service.open('pdf1/capa.php?type=capadigital&capa_no='+capa_no);
    }
  }
}