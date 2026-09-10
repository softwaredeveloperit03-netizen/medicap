import { ComponentFixture, TestBed } from '@angular/core/testing';

import { StoragecondiComponent } from './storagecondi.component';

describe('StoragecondiComponent', () => {
  let component: StoragecondiComponent;
  let fixture: ComponentFixture<StoragecondiComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ StoragecondiComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(StoragecondiComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
