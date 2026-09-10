import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DatabackuplogComponent } from './databackuplog.component';

describe('DatabackuplogComponent', () => {
  let component: DatabackuplogComponent;
  let fixture: ComponentFixture<DatabackuplogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DatabackuplogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DatabackuplogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
