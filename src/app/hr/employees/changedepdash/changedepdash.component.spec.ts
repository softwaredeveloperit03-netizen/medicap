import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ChangedepdashComponent } from './changedepdash.component';

describe('ChangedepdashComponent', () => {
  let component: ChangedepdashComponent;
  let fixture: ComponentFixture<ChangedepdashComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ChangedepdashComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ChangedepdashComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
