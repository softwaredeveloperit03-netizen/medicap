import { ComponentFixture, TestBed } from '@angular/core/testing';

import { VendorLogComponent } from './vendor-log.component';

describe('VendorLogComponent', () => {
  let component: VendorLogComponent;
  let fixture: ComponentFixture<VendorLogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ VendorLogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(VendorLogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
