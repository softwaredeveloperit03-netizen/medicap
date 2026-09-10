import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-kf',
  templateUrl: './kf.component.html',
  styleUrls: ['./kf.component.css']
})
export class KfComponent implements OnInit {
  isNew= false;

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
  }

  new() {
    this.isNew = true;
  }

}
