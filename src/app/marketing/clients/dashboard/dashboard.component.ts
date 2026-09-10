import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import {
  getDescriptionLines,
  getSavedEntryProductsLabel,
  parseClientServiceEntries,
  SavedServiceEntry,
} from '../client-service.helper';
import * as ExcelJS from 'exceljs';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  isUser = false;
  isChecker = false;
 
  isView = false;
  savedServiceEntries: SavedServiceEntry[] = [];
 
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getclientlist();
    this.get_rights();
    this.getAgents();
  }

  clients;
  clientSearch = '';

  getclientlist() {
    this.service.get('marketing/client.php?type=getClientsDetails').subscribe((response: any) => {
        this.clients = response;
    });
  }

  /** Filter clients by client name (Legal Name / Brand Name) for search */
  getFilteredClients(): any[] {
    if (!this.clients || !Array.isArray(this.clients)) {
      return [];
    }
    const q = (this.clientSearch || '').trim().toLowerCase();
    if (!q) {
      return this.clients;
    }
    return this.clients.filter((c: any) => {
      const name = (c.LglNm || '').toLowerCase();
      const brand = (c.TrdNm || '').toLowerCase();
      return name.indexOf(q) !== -1 || brand.indexOf(q) !== -1;
    });
  }

  /** Download client list (filtered) as formatted Excel */
  downloadClientList() {
    const list = this.getFilteredClients();
    if (!list || list.length === 0) {
      alertify.warning('No data to download.');
      return;
    }
    const formatDate = (d: any) => {
      if (!d) return '-';
      const dt = new Date(d);
      return isNaN(dt.getTime()) ? '-' : dt.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
    };
    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet('Client List', { pageSetup: { orientation: 'landscape' } });
    const headers = ['Sr.No.', 'Client Name', 'Brand Name', 'Type of Client', 'Date of Onboarding', 'Status', 'Entry By', 'Entry On', 'Approval By', 'Approved Date'];
    const headerRow = ws.addRow(headers);
    headerRow.eachCell((cell) => {
      cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF0e4370' } };
      cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 };
      cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
      cell.border = {
        top: { style: 'thin' },
        bottom: { style: 'thin' },
        left: { style: 'thin' },
        right: { style: 'thin' },
      };
    });
    ws.getRow(1).height = 22;
    list.forEach((c: any, i: number) => {
      ws.addRow([
        i + 1,
        c.LglNm || '-',
        c.TrdNm || '-',
        c.client_type || '-',
        formatDate(c.dateOfOnboarding),
        (c.status || '-').toUpperCase(),
        c.entry_by || '-',
        formatDate(c.entry_date),
        c.approvedBy || '-',
        formatDate(c.approvedOn)
      ]);
    });
    const thinBorder = { top: { style: 'thin' as const }, bottom: { style: 'thin' as const }, left: { style: 'thin' as const }, right: { style: 'thin' as const } };
    for (let r = 2; r <= list.length + 1; r++) {
      const row = ws.getRow(r);
      row.height = 20;
      row.eachCell((cell) => {
        cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
        cell.border = thinBorder;
      });
    }
    const colWidths = [8, 18, 18, 14, 16, 10, 12, 14, 14, 14];
    ws.columns.forEach((col, idx) => { col.width = colWidths[idx] ?? 14; });
    wb.xlsx.writeBuffer().then((buffer: ArrayBuffer) => {
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `client-list-${new Date().toISOString().slice(0, 10)}.xlsx`;
      a.click();
      URL.revokeObjectURL(url);
    });
  }

  agents;
  getAgents() {
    this.service.get('marketing/agent.php?type=getApprovedAgents').subscribe((response) => {
        this.agents = response;
    });
  }

  selectedClient = [];
  view(item) {
    this.selectedClient = {...item};
    this.savedServiceEntries = parseClientServiceEntries(this.selectedClient);
    this.isView = true;
  }

  getSavedEntryProductsLabel(entry: SavedServiceEntry): string {
    return getSavedEntryProductsLabel(entry);
  }

  getDescriptionLines(entry: SavedServiceEntry): string[] {
    return getDescriptionLines(entry.descriptions);
  }

  isViewUpload = false;
  agriUp(item) {
    this.selectedClient = [];
    this.selectedClient = item
    this.isViewUpload = !this.isViewUpload;
    this.getClientAgrement(this.selectedClient['client_code']);
  }

 

  updateClient(data) {

    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = this.selectedClient;
    this.service.post('marketing/client.php?type=updateClient', JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          this.getclientlist();
          alert('Client Updated Successfully');
          this.isView = false;
        } else {
          alert('Please try Again');
        }
      });
  }

 

 
  isuser = 'No';
  ischecker = 'No';
  rights;

  get_rights() {
   this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id') +'&dep_name=' +localStorage.getItem('department') ).subscribe((response) => {
       this.rights = response;
       this.isuser = this.rights[0].isuser;
       this.ischecker = this.rights[0].ischecker;
    });
  }

  agreFile: File;
  onFileChange2($event) {
    this.agreFile = $event.target.files[0];
  }

    uploadAgreement(form: NgForm) {
      if (!form.valid) {
        alert('All fields are required');
        return;
      }

      const formData = new FormData();
      formData.append('client_code', this.selectedClient['client_code']);
      formData.append('agree_name', form.value.agree_name);
      formData.append('valid_till', form.value.valid_till);
      formData.append('doc_type', form.value.doc_type);

      if (this.agreFile) {
        formData.append('agreFile', this.agreFile, this.agreFile.name);
      }

      this.service.post('marketing/client.php?type=uploadClientAgrement', formData)
        .subscribe((response) => {
          const result = JSON.parse(JSON.stringify(response));
          if (result.status === 'success') {
            form.resetForm();
            alert('Saved Successfully');
            this.getClientAgrement(this.selectedClient['client_code']);
          } else {
            alert('An error has occurred: ' + result.status);
          }
        });
    }

  ClientAgrement;

  getClientAgrement(client_code) {
    this.service.get('marketing/client.php?type=getClientAgrement&client_code=' + client_code).subscribe((response: any) => {
        this.ClientAgrement = response;
    });
  }

  viewAgree(url) {
    url = this.service.url + '../../upload/client/' + url;
    window.open(url, '_blank');
  }

  isPastDate(date: string): boolean {
    const currentDate = new Date();
    const holidayDate = new Date(date);
    return holidayDate < currentDate;
  }

  
  
  openPerson(){
    this.isPerson = !this.isPerson;
  }

  openBranch(){
    this.isBranch = !this.isBranch;
  }




      countries: string[] = [
    "Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra",
    "Angola", "Anguilla", "Antigua & Barbuda", "Argentina", "Armenia",
    "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas",
    "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium",
    "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia",
    "Bosnia & Herzegovina", "Botswana", "Brazil", "Brunei", "Bulgaria",
    "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada",
    "Chile", "China", "Colombia", "Costa Rica", "Croatia",
    "Cuba", "Cyprus", "Czech Republic", "Denmark", "Dominican Republic",
    "Ecuador", "Egypt", "El Salvador", "Estonia", "Ethiopia",
    "Fiji", "Finland", "France", "Germany", "Greece",
    "Hong Kong", "Hungary", "Iceland", "India", "Indonesia",
    "Iran", "Iraq", "Ireland", "Israel", "Italy",
    "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya",
    "Kuwait", "Latvia", "Lebanon", "Lithuania", "Luxembourg",
    "Malaysia", "Maldives", "Malta", "Mexico", "Monaco",
    "Mongolia", "Morocco", "Myanmar", "Nepal", "Netherlands",
    "New Zealand", "Nigeria", "Norway", "Oman", "Pakistan",
    "Panama", "Peru", "Philippines", "Poland", "Portugal",
    "Qatar", "Romania", "Russia", "Saudi Arabia", "Singapore",
    "Slovakia", "Slovenia", "South Africa", "South Korea", "Spain",
    "Sri Lanka", "Sweden", "Switzerland", "Syria", "Taiwan",
    "Tanzania", "Thailand", "Trinidad & Tobago", "Tunisia", "Turkey",
    "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States of America",
    "Uruguay", "Uzbekistan", "Venezuela", "Vietnam", "Yemen", "Zambia", "Zimbabwe"
  ];



  states: string[] = ["Andhra Pradesh","Andaman and Nicobar Islands","Arunachal Pradesh","Assam",
    "Bihar","Chandigarh","Chhattisgarh","Dadra and Nagar Haveli","Daman and Diu","Delhi",
    "Lakshadweep","Puducherry","Goa","Gujarat","Haryana","Himachal Pradesh","Jammu and Kashmir",
    "Jharkhand","Karnataka","Kerala","Madhya Pradesh","Maharashtra","Manipur","Meghalaya","Mizoram",
    "Nagaland","Odisha","Punjab","Rajasthan","Sikkim","Tamil Nadu","Telangana","Tripura",
    "Uttar Pradesh","Uttarakhand","West Bengal"
  ];


  branches = [];
  isPerson = false;
  isBranch = false;
  personData = [];

  addPerson(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['type'] = 'NEW';
    this.selectedClient['clientContactPerson'].push(temp);
    data.reset();
    this.isPerson = false;
  }

  delPerson(index) {
    this.selectedClient['clientContactPerson'].splice(index, 1);
  }

  add(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['type'] = 'NEW';
    this.selectedClient['clientBranches'].push(temp)
    data.resetForm();
    this.isBranch = false;
  }

  delBranch(ind) {
    this.selectedClient['clientBranches'].splice(ind, 1);
  }












}
