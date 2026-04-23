const https = require("https");
const fs = require("fs");
const path = require("path");

const galleryImages = [
  "https://mmodels.ca/uploads/3/4/6/1/34611475/824771ab-472f-4232-bbac38c9e1e06bb9_orig.jpg",
  "https://mmodels.ca/uploads/3/4/6/1/34611475/2c0f3943-2f91-4610-8702-d2286703bb69-lighttable_orig.jpg",
  "https://mmodels.ca/uploads/3/4/6/1/34611475/img-1128_orig.jpg",
  "https://mmodels.ca/uploads/3/4/6/1/34611475/5d3f3461-30d8-4302-810da2acfc6ba3e7-1_orig.jpg",
  "https://mmodels.ca/uploads/3/4/6/1/34611475/e2700392-d214-462e-b1aff444f93d7121-1_orig.jpg",
  "https://mmodels.ca/uploads/3/4/6/1/34611475/fb7cb327-b17b-4d50-91a637b66da21355_orig.jpg",
  "https://mmodels.ca/uploads/3/4/6/1/34611475/5e7ad5cd-b732-4fe6-ba86d340d5b0d37a-1_orig.jpg",
  "https://mmodels.ca/uploads/3/4/6/1/34611475/cf1385f5-5a2e-4ca0-9f42816c4ae05851_orig.jpg",
  "https://mmodels.ca/uploads/3/4/6/1/34611475/8e46bdfd-401f-4b5e-b32bc02d349e162f_orig.jpg",
  "https://mmodels.ca/uploads/3/4/6/1/34611475/img-2309_orig.jpg",
  "https://mmodels.ca/uploads/3/4/6/1/34611475/fb0ee577-315c-4767-b3150d9508bc12fb-1_orig.jpg",
  "https://mmodels.ca/uploads/3/4/6/1/34611475/340dbb33-949f-4508-8dda1ed52f54adc4_orig.jpg",
  "https://mmodels.ca/uploads/3/4/6/1/34611475/965b6198-dded-4a15-81434bbd7e8baafd_orig.jpg",
  "https://mmodels.ca/uploads/3/4/6/1/34611475/img-20190723-123808-232_orig.jpg",
  "https://mmodels.ca/uploads/3/4/6/1/34611475/img-20190623-170829-730_orig.jpg"
];

const dir = "./downloaded_images";
if (!fs.existsSync(dir)) fs.mkdirSync(dir);

const downloadWithDelay = async () => {
  for (let i = 0; i < galleryImages.length; i++) {
    const url = galleryImages[i];
    const filename = path.basename(url);
    const filePath = path.join(dir, filename);

    // Options mein User-Agent dalna zaruri hai
    const options = {
      headers: {
        "User-Agent":
          "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
      },
    };

    await new Promise((resolve) => {
      https
        .get(url, options, (res) => {
          if (res.statusCode === 200) {
            const fileStream = fs.createWriteStream(filePath);
            res.pipe(fileStream);
            fileStream.on("finish", () => {
              console.log(`✅ Downloaded: ${filename}`);
              fileStream.close();
              resolve();
            });
          } else {
            console.log(`❌ Failed ${filename}: Status ${res.statusCode}`);
            resolve();
          }
        })
        .on("error", (err) => {
          console.log(`Error: ${err.message}`);
          resolve();
        });
    });

    // 500ms ka intezar har download ke baad
    await new Promise((r) => setTimeout(r, 500));
  }
  console.log("--- All downloads finished ---");
};

downloadWithDelay();
