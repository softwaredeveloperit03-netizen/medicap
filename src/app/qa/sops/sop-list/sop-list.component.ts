import { HttpClient } from '@angular/common/http';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-sop-list',
  templateUrl: './sop-list.component.html',
  styleUrls: ['./sop-list.component.css']
})
export class SOPListComponent implements OnInit {

  soplist = [];
  constructor(private service: DataAccessService, private http: HttpClient){ }

  ngOnInit(): void {
    this.getsoplist();
  }

  getsoplist(): void {
    this.service.get('sops.php?type=getCreatedSOPlist').subscribe((response: any) => {
      this.soplist = response;
    });
  }

  downloadsop(file): void {
    window.open(this.service.url + 'sops/' + file);
  }

}
