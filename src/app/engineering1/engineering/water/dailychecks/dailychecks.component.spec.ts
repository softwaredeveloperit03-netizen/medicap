import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DailychecksComponent } from './dailychecks.component';

describe('DailychecksComponent', () => {
  let component: DailychecksComponent;
  let fixture: ComponentFixture<DailychecksComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DailychecksComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DailychecksComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
