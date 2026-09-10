import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ShiftchngeComponent } from './shiftchnge.component';

describe('ShiftchngeComponent', () => {
  let component: ShiftchngeComponent;
  let fixture: ComponentFixture<ShiftchngeComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ShiftchngeComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ShiftchngeComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
