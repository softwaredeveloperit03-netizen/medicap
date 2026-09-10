import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MasterVendorComponent } from './master-vendor.component';

describe('MasterVendorComponent', () => {
  let component: MasterVendorComponent;
  let fixture: ComponentFixture<MasterVendorComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MasterVendorComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MasterVendorComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
