import { ComponentFixture, TestBed } from '@angular/core/testing';

import { IntimationComponent } from './intimation.component';

describe('IntimationComponent', () => {
  let component: IntimationComponent;
  let fixture: ComponentFixture<IntimationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ IntimationComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(IntimationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
