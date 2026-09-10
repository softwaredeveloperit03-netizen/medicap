import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ShiftapprComponent } from './shiftappr.component';

describe('ShiftapprComponent', () => {
  let component: ShiftapprComponent;
  let fixture: ComponentFixture<ShiftapprComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ShiftapprComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ShiftapprComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
