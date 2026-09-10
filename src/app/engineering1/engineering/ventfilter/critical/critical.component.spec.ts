import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CriticalComponent } from './critical.component';

describe('CriticalComponent', () => {
  let component: CriticalComponent;
  let fixture: ComponentFixture<CriticalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CriticalComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(CriticalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
