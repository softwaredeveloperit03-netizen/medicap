import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-inwardgoodreceive',
  templateUrl: './inwardgoodreceive.component.html',
  styleUrls: ['./inwardgoodreceive.component.css']
})
export class InwardgoodreceiveComponent implements OnInit {
isView=false
  subtypes: any;
  material_type:any
  material_subtype:any
  results: any
constructor(public service: DataAccessService, private router: Router) {
  this.material_type="Microbiology Materials"

}

  ngOnInit(): void {
    this.getgensubtype()


  }
  getgensubtype() {
    this.service.get('master/materialtype.php?type=get_gen_material_subtype&material_type=').subscribe((response: any) => {
      this.subtypes = response;
    });
  }

  getGRNLog() {
    this.service.get('qc/chemical.php?type=getMicrobiologyGRNLog&material_subtype='+this.material_subtype).subscribe(response => {
      this.results = response;
      console.log(this.results)
    });
  }

  viewResult(index)
  {
    
  }

}
