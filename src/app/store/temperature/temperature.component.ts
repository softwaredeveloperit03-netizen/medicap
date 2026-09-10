  import { DatePipe } from '@angular/common';
  import { Component, OnInit } from '@angular/core';
  import { ActivatedRoute } from '@angular/router';
  import { DataAccessService } from 'src/app/data-access.service';
  declare let alertify;  

@Component({
  selector: 'app-temperature',
  templateUrl: './temperature.component.html',
  styleUrls: ['./temperature.component.css'],
  providers:[DatePipe]  
})
export class TemperatureComponent implements OnInit {
    loading;
    from_date = '';
    to_date = '';
    today = '';
    results;
    isNew=false;
    isView = false;
    selectedResult=[];
    ids;
    sectionAreas: any[] = [];
    currentPage: number;
    pageSize: number;
    temperatureDepartment = 'Store';
    closeRoute = '/store';
    apiBase = 'store/temperature.php';

    constructor(
      private service : DataAccessService,
      private datePipe :DatePipe,
      private route: ActivatedRoute,
    ) {
      this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
      this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
      this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
      this.loggedInDept = localStorage.getItem('department');
    }
  
    ngOnInit(): void {
      const data = this.route.snapshot.data || {};
      this.temperatureDepartment = (data['temperatureDepartment'] || 'Store').toString();
      this.closeRoute = (data['closeRoute'] || '/store').toString();
      this.apiBase = (data['apiBase'] || 'store/temperature.php').toString();
      this.getTemp();
      this.getIds();
      this.loadSectionAreas();
      this.get_rights();
    }

    private masterSectionDepartment(): string {
      const map: Record<string, string> = {
        Store: 'Material Management',
        Warehouse: 'Material Management',
        Production: 'Production',
        QC: 'Quality Control',
      };
      return map[this.temperatureDepartment] || this.temperatureDepartment;
    }

    private normalizeSectionAreas(rows: any[]): any[] {
      const seen = new Set<string>();
      return (rows || [])
        .filter((row) => {
          const name = (row?.section_name || '').toString().trim();
          if (!name || seen.has(name.toLowerCase())) {
            return false;
          }
          seen.add(name.toLowerCase());
          return true;
        })
        .sort((a, b) => String(a.section_name).localeCompare(String(b.section_name)));
    }

    loadSectionAreas(): void {
      const primaryDept = this.masterSectionDepartment();
      this.service.get('master/section.php?type=getSection1&dept=' + encodeURIComponent(primaryDept)).subscribe(
        (response: any) => {
          let rows = Array.isArray(response) ? response : [];
          if (rows.length === 0 && primaryDept !== this.temperatureDepartment) {
            this.service.get(
              'master/section.php?type=getSection1&dept=' + encodeURIComponent(this.temperatureDepartment)
            ).subscribe((fallback: any) => {
              rows = Array.isArray(fallback) ? fallback : [];
              this.sectionAreas = this.normalizeSectionAreas(rows);
            }, () => {
              this.sectionAreas = [];
            });
            return;
          }
          this.sectionAreas = this.normalizeSectionAreas(rows);
        },
        () => {
          this.sectionAreas = [];
        }
      );
    }

    openNewModal(): void {
      this.fromlevel = [];
      this.fromlevel1 = [];
      this.loadSectionAreas();
      this.isNew = true;
    }

    private apiQuery(extra = ''): string {
      const dept = encodeURIComponent(this.temperatureDepartment);
      const prefix = extra.includes('?') ? '&' : '?';
      return `${this.apiBase}${extra}${prefix}department=${dept}`;
    }
    

    isuser = 'No';
    ischecker = 'No';
    isapprover = 'No';
    qms_approver = 'No';
    dept_head = 'No';
    isauditor = 'No';
    plant_head = 'No';
    shift_allocator = 'No';
    rights;
    loggedInDept;
  
    get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
      +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
         )
        .subscribe((response) => {
          this.rights = response;
          this.isuser = this.rights[0].isuser;
          this.ischecker = this.rights[0].ischecker;
          this.isapprover = this.rights[0].isapprover;
          this.qms_approver = this.rights[0].qms_approver;
          this.dept_head = this.rights[0].dept_head;
          this.isauditor = this.rights[0].isauditor;
          this.plant_head = this.rights[0].plant_head;
          this.shift_allocator = this.rights[0].shift_allocator;
        });
    }
  
    getIds(){
      this.service.get(this.apiQuery('?type=getThermohygrometers')).subscribe(response =>{
        this.ids = response;
   
      });
    }
    
    getTemp(){
      this.service.get(
        this.apiQuery('?type=getTemperature&from_date=' + this.from_date + '&to_date=' + this.to_date)
      ).subscribe(response =>{
        this.results = response;
      });
    }


    selectedresult=[];
    view(index){
      this.selectedResult = this.results[index];
      this.isView = true;
    }
 
   
   
  
    download(){
      this.service.open(this.apiQuery('?type=downloadTemperature&from_date=' + this.from_date + '&to_date=' + this.to_date));
    }
    download1(){
      this.service.open(this.apiQuery('?type=downloadTemperature1&equpment_code=' + this.ids['equipment_code']));
    }

    fromlevel1=[];
    addlabel1(data) {
      if (!data.valid) {
        alertify.error('All fields are required');
        return;
      }
      // this.fromlevel1=[];
        let temp1 = data.value;
       this.fromlevel1[this.fromlevel1.length] =temp1;
           console.log("ad",this.fromlevel1);
           data.reset();
     }
     
    fromlevel=[];
    addlabel(data) {
      if (!data.valid) {
        alertify.error('All fields are required');
        return;
      }
      // this.fromlevel=[];
        let temp2 = data.value;
         this.fromlevel[this.fromlevel.length] =temp2;
           console.log("rd",this.fromlevel);
           data.reset();
    }

    deleteLabel1(index){
      this.fromlevel.splice(index,1);
    }
    deleteLabel(index){
      this.fromlevel1.splice(index,1);
    }

    save(data){
      if (!data.valid) {
        alertify.error('All fields are required');
        return;
      }
      let temp = data.value; 
      temp['fromlevel']=this.fromlevel;
      temp['fromlevel1']=this.fromlevel1;
      this.service.post(this.apiQuery('?type=saveTemperature'), JSON.stringify(temp)).subscribe(response =>{
        if (response['status'] === 'success') {
          this.getTemp();
          this.loadSectionAreas();
          alertify.success('Record Inserted successfully');
          this.isNew=false;
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
    }
  
  }
  