import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { ActivatedRoute, Router } from '@angular/router';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';

@Component({
  selector: 'app-coa',
  templateUrl: './coa.component.html',
  styleUrls: ['./coa.component.css'],
  providers:[DatePipe]
})
export class CoaComponent implements OnInit {

  materials: any[] = [];
  materialNames = new Set<string>();
  results: any[] = [];
  results1: any[] = [];
  from_date='';
  to_date='';
  material_name='';
  selectedOrder: any = null;
  isInit=true;
  tests: any[] = [];
  loadingTests = false;

  constructor(private service:DataAccessService ,private datePipe:DatePipe,private router: Router,
    private route: ActivatedRoute, private deptNav: DeptNavigationService) {     
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit() {
    this.getTestings();
    this.getMaterials();
  }

  getMaterials(){
    this.loadMaterialsOfType('Raw Material');
    this.loadMaterialsOfType('Packing Material');
  }

  loadMaterialsOfType(materialType: string){
    this.service.get('master/material.php?type=getMaterials&material_type=' + encodeURIComponent(materialType)).subscribe(response => {
      const rows = Array.isArray(response) ? response : [];
      for(let i=0; i<rows.length; i++){
        const status = String(rows[i]['status'] || '').trim().toLowerCase();
        if(status == 'approved' || status == 'approve'){
          this.addMaterialName(rows[i]['material_name']);
        }
      }
      this.refreshMaterialOptions();
    });
  }

  close(){
    this.isInit=true;
    this.tests = [];
    this.selectedOrder = null;
    this.loadingTests = false;
  }

  closePage(){
    this.deptNav.goBack(this.route, '/qc/testing-rds');
  }

  getTestings() {
    this.service.getJsonArray('qc/testing/raw.php?type=getTestingReport&include_tests=0').subscribe({
      next: (response) => {
        this.results = Array.isArray(response) ? response : [];
        this.results1 = this.results;
        this.buildMaterialList();
      },
      error: () => {
        this.results = [];
        this.results1 = [];
      }
    });
  }

  // Materials shown in the filter come from the master list plus any name present in the loaded COA rows.
  buildMaterialList(){
    const rows = Array.isArray(this.results1) ? this.results1 : [];
    for(let i=0; i<rows.length; i++){
      this.addMaterialName(rows[i]['material_name']);
    }
    this.refreshMaterialOptions();
  }

  addMaterialName(name){
    if(name != null && String(name).trim() != ''){
      this.materialNames.add(String(name).trim());
    }
  }

  refreshMaterialOptions(){
    this.materials = Array.from(this.materialNames).sort().map(name => ({ material_name: name }));
  }

  downloadCoaLog() {
    let url = 'qc/testing/raw.php?type=downloadCoaLog';
    if (this.material_name) {
      url += '&material_name=' + encodeURIComponent(this.material_name);
    }
    this.service.open(url);
  }

  viewCOA(testingNo?: string) {
    const no = String(
      testingNo ||
        (this.selectedOrder && this.selectedOrder['testing_no']) ||
        ''
    ).trim();
    if (!no) {
      return;
    }
    this.service.open(
      'qc/testing/raw.php?type=downloadTestingCOAReport&testing_no=' + encodeURIComponent(no)
    );
  }
  view(data){
    this.selectedOrder = data || {};
    this.tests = [];
    this.isInit = false;
    this.loadCoaTests(this.selectedOrder['testing_no'], this.selectedOrder['specification_no']);
  }

  loadCoaTests(testingNo: string, specificationNo?: string) {
    if (!testingNo) {
      this.tests = [];
      return;
    }
    this.loadingTests = true;
    let url =
      'qc/testing/raw.php?type=getTestByTestingNOForApproval&with_spec=1&testing_no=' +
      encodeURIComponent(testingNo);
    if (specificationNo) {
      url += '&specification_no=' + encodeURIComponent(specificationNo);
    }
    this.service.getJsonArray(url).subscribe({
      next: (response) => {
        this.tests = Array.isArray(response) ? response : [];
        if (this.tests.length === 0) {
          this.loadCoaTestsPlain(testingNo);
          return;
        }
        this.loadingTests = false;
      },
      error: () => {
        this.loadCoaTestsPlain(testingNo);
      }
    });
  }

  private loadCoaTestsPlain(testingNo: string) {
    const url =
      'qc/testing/raw.php?type=getTestByTestingNOForApproval&testing_no=' +
      encodeURIComponent(testingNo);
    this.service.getJsonArray(url).subscribe({
      next: (response) => {
        this.tests = Array.isArray(response) ? response : [];
        this.loadingTests = false;
      },
      error: () => {
        this.tests = [];
        this.loadingTests = false;
      }
    });
  }

  filterStock(){
    const source = Array.isArray(this.results1) ? this.results1 : [];
    if(this.material_name == null || String(this.material_name).trim() == ''){
      this.results = source;
      return;
    }
    const q = String(this.material_name).toUpperCase();
    this.results = [];
    for(let i=0; i<source.length; i++){
      let data = source[i];
      if(data && data.material_name!=null){
        if(String(data.material_name).toUpperCase().includes(q)){
          this.results.push(data);
        }
      }
    }
  }

  clear(){
    this.material_name = '';
    this.results = Array.isArray(this.results1) ? this.results1 : [];
  }



  viewPhoto(url) {
    window.open(this.service.url + '../../upload/outside_testing_report/' + url);
  }






}
