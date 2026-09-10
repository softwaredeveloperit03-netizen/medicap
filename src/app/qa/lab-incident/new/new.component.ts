import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
save: any;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
  }
other;
other1;
}
