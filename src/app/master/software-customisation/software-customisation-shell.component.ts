import { Component } from '@angular/core';
import { SC_PATHS } from './software-customisation-paths';

interface ScTab {
  id: string;
  label: string;
  route: string;
  icon: string;
}

@Component({
  selector: 'app-software-customisation-shell',
  templateUrl: './software-customisation-shell.component.html',
  styleUrls: ['./software-customisation-shell.component.css'],
})
export class SoftwareCustomisationShellComponent {
  readonly deptId = 'forms';
  readonly tabs: ScTab[] = [
    {
      id: 'material-master-form',
      label: 'Material Master Form',
      route: SC_PATHS.materialMasterForm,
      icon: 'fa-sliders-h',
    },
    {
      id: 'specification-form',
      label: 'Specification Customisation',
      route: SC_PATHS.specificationForm,
      icon: 'fa-file-medical',
    },
    {
      id: 'receiving-form',
      label: 'Receiving Form Customisation',
      route: SC_PATHS.receivingForm,
      icon: 'fa-truck-loading',
    },
    {
      id: 'software-type',
      label: 'Software Type',
      route: SC_PATHS.softwareType,
      icon: 'fa-laptop-code',
    },
    {
      id: 'moa-type',
      label: 'MOA Type',
      route: SC_PATHS.moaType,
      icon: 'fa-flask',
    },
  ];

  trackByTabId(_index: number, tab: ScTab): string {
    return tab.id;
  }
}
