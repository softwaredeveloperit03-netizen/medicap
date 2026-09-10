import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MfglinesComponent } from './mfglines.component';

describe('MfglinesComponent', () => {
  let component: MfglinesComponent;
  let fixture: ComponentFixture<MfglinesComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MfglinesComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(MfglinesComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
