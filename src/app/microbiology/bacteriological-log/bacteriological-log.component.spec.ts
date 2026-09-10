import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BACTERIOLOGICALLogComponent } from './bacteriological-log.component';

describe('BACTERIOLOGICALLogComponent', () => {
  let component: BACTERIOLOGICALLogComponent;
  let fixture: ComponentFixture<BACTERIOLOGICALLogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BACTERIOLOGICALLogComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(BACTERIOLOGICALLogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
