import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-dataretrive',
  templateUrl: './dataretrive.component.html',
  styleUrls: ['./dataretrive.component.css']
})
export class DataretriveComponent implements OnInit {
  isNew = false;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
  }

  dataret(){
    this.isNew = true;
  }

}
