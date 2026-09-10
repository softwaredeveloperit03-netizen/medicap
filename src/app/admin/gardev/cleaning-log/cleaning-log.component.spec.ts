import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CleaningLogComponent } from './cleaning-log.component';

describe('CleaningLogComponent', () => {
  let component: CleaningLogComponent;
  let fixture: ComponentFixture<CleaningLogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CleaningLogComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(CleaningLogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
