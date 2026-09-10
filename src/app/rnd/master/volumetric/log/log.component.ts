import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  results;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getVolumetricMaster();
  }

  getVolumetricMaster() {
    this.service.get('qc/volumetric.php?type=getVolumetricMaster').subscribe(response => {
      this.results = response;
    });
  }

  download() {
    this.service.open('qc/volumetric.php?type=downloadVolumetricMaster')
  }

}
