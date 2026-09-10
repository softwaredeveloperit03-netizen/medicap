import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CleaningrecComponent } from './cleaningrec.component';

describe('CleaningrecComponent', () => {
  let component: CleaningrecComponent;
  let fixture: ComponentFixture<CleaningrecComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CleaningrecComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CleaningrecComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
