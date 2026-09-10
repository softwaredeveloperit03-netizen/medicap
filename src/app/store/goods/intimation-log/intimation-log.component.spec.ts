import { ComponentFixture, TestBed } from '@angular/core/testing';

import { IntimationLogComponent } from './intimation-log.component';

describe('IntimationLogComponent', () => {
  let component: IntimationLogComponent;
  let fixture: ComponentFixture<IntimationLogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ IntimationLogComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(IntimationLogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
