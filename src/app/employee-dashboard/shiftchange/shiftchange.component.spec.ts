import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ShiftchangeComponent } from './shiftchange.component';

describe('ShiftchangeComponent', () => {
  let component: ShiftchangeComponent;
  let fixture: ComponentFixture<ShiftchangeComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ShiftchangeComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ShiftchangeComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
