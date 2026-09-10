import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-dossiernew',
  templateUrl: './dossiernew.component.html',
  styleUrls: ['./dossiernew.component.css'],
  providers:[DatePipe]

})
export class DossiernewComponent implements OnInit {
  

  results;
  list;
  items;
  from_date='';
  to_date='';
  generic_name='';
  brand_name='';
  selectedEntry = [];
  isView = false;
  constructor(private service:DataAccessService ,private datePipe:DatePipe) { 
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }
  ngOnInit() {
    this.getDossierList();
  }

  viewDossier(index) {
    this.selectedEntry = this.list[index];
    this.isView = true;
  }

  getDossierList() {
    this.service.get('marketing/dossier.php?type=getDossiersLog&form_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
      this.list = response;
    });
  }

  viewfile(file) {
    window.open(this.service.url + 'upload/dossier/' + file);
  }

  
}
